#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Submitty Database Sync Command Line Tool

Submitty's database structure works with a "master" database and separate
databases for each course.  When a user (instructor, grader, student) is added
or updated to a course, they are first added/updated in the "master" database,
and then an automatic trigger will execute to ensure the addition/update is also
applied to the appropriate course database.

Should data between the "master" database and a course database no longer match,
this tool will reconcile the differences under these rules:

* all user records are determined by the User ID field.
* user records in the "master" database have precedence over records in the
  course database.  "master" database records will overwrite inconsistencies
  found in a course database.
* users recorded in the "master" database, but not existing in the course
  database will be copied to the course database
* users recorded in the course database, but not esixting in the "master"
  database will be copied over to the "master" database.

**IMPORTANT**
* This tool only works with Postgresql databases.
* Requires the ``psycopg2`` python library.
"""

import datetime
import os
import psycopg2
import sys

# CONFIGURATION ----------------------------------------------------------------
DB_HOST = 'localhost'
DB_USER = 'hsdbu'
DB_PASS = 'hsdbu'  # Do NOT use this password in production
# ------------------------------------------------------------------------------

class db_sync:
	"""Sync user data in course databases with master database"""

	MASTER_DB_CONN = None
	"""psycopg2 connection resource for Submitty master DB"""
	MASTER_DB_CUR  = None
	"""psycopg2 "cursor" resource for Submitty master DB"""


	COURSE_DB_CONN = None
	"""psycopg2 connection resource for a Submitty course DB"""
	COURSE_DB_CUR  = None
	"""psycopg2 "cursor" resource for Submitty course DB"""

	SEMESTER = None
	"""current semester code (e.g. s18 = Spring 2018)"""

	def __init__(self):
		"""Auto start main process"""

		self.main()

	def __del__(self):
		"""Cleanup DB connections"""

		self.master_db_disconnect()
		self.course_db_disconnect()

# ------------------------------------------------------------------------------

	def main(self):
		"""Main Process"""

		if len(sys.argv) < 2 or sys.argv[1] == 'help':
			self.print_help()
			sys.exit(0)
		elif sys.argv[1] == 'all':
			# Get complete course list
			self.master_db_connect()
			course_list = self.get_all_courses()
		else:
			# Validate that courses exist
			self.master_db_connect()
			all_courses = self.get_all_courses()
			course_list = tuple(course for course in sys.argv[1:] if course in all_courses)
			invalid_course_list = tuple(course for course in sys.argv[1:] if course not in course_list)

			# Check that invalidated_course_list is not empty
			if invalid_course_list:
				# Get user permission to proceed
				# Clear console
				os.system('cls' if os.name == 'nt' else 'clear')
				print("The following courses are invalid:" + os.linesep + str(invalid_course_list)[1:-1] + os.linesep)
				# Check that course_list is empty.
				if not course_list:
					raise SystemExit("No valid courses specified.")

				print("Proceed syncing valid courses?" + os.linesep + str(course_list)[1:-1] + os.linesep)
				if input("Y/[N]:").lower() != 'y':
					print("exiting...")
					sys.exit(0)

		# Process database sync
		self.SEMESTER = self.determine_semester()
		for index, course in enumerate(course_list):
			self.course_db_connect(course)
			masterdb_users, coursedb_users = self.retrieve_all_users(course)
			common_users = tuple(user for user in coursedb_users if user in masterdb_users)
			masterdb_unique_users = tuple(user for user in masterdb_users if user not in coursedb_users)
			coursedb_unique_users = tuple(user for user in coursedb_users if user not in masterdb_users)
			self.reconcile_master_course(common_users)

			# TO DO: Call functions to send SQL queries
			# Update master db to course db
			# insert unique master users to course db
			# insert unique course users to master db
			# disconnect from course db
			self.course_db_disconnect()

# ------------------------------------------------------------------------------

	def master_db_connect(self):
		"""
		Establish connection to Submitty Master DB

		:raises SystemExit:  Master DB connection failed.
		"""

		try:
			self.MASTER_DB_CONN = psycopg2.connect("dbname=submitty user={} host={} password={}".format(DB_USER, DB_HOST, DB_PASS))
			self.MASTER_DB_CUR  = self.MASTER_DB_CONN.cursor()
		except Exception as e:
			raise SystemExit("ERROR: Cannot connect to Submitty master database" + os.linesep + str(e))

# ------------------------------------------------------------------------------

	def master_db_disconnect(self):
		"""Close an open cursor connection to Submitty "master" DB"""

		if hasattr(self.MASTER_DB_CUR, 'closed') and self.MASTER_DB_CUR.closed == False:
			self.MASTER_DB_CUR.close()

		if hasattr(self.MASTER_DB_CONN, 'closed') and self.MASTER_DB_CONN.closed == 0:
			self.MASTER_DB_CONN.close()

# ------------------------------------------------------------------------------

	def course_db_connect(self, course):
		"""
		Establish connection to a Submitty course DB

		:param course:   course name
		:return:         flag indicating connection success or failure
		:rtype:          boolean
		"""

		db_name = "submitty_{}_{}".format(self.SEMESTER, course)

		try:
			self.COURSE_DB_CONN = psycopg2.connect("dbname={} user={} host={} password={}".format(db_name, DB_USER, DB_HOST, DB_PASS))
			self.COURSE_DB_CUR  = self.COURSE_DB_CONN.cursor()
		except:
			return False

		return True

# ------------------------------------------------------------------------------

	def course_db_disconnect(self):
		"""Close an open cursor and connecton to a Submitty course DB"""

		if hasattr(self.COURSE_DB_CUR, 'closed') and self.COURSE_DB_CUR.closed == False:
			self.COURSE_DB_CUR.close()

		if hasattr(self.COURSE_DB_CONN, 'closed') and self.MASTER_DB_CONN.closed == 0:
			self.COURSE_DB_CONN.close()

# ------------------------------------------------------------------------------

	def get_all_courses(self):
		"""
		Retrieve active course list from Master DB

		:return: list of all active courses
		:rtype:  list (string)
		"""

		self.MASTER_DB_CUR.execute("SELECT course FROM courses WHERE semester='{}'".format(self.determine_semester()))
		return tuple(row[0] for row in self.MASTER_DB_CUR.fetchall())

# ------------------------------------------------------------------------------
	def retrieve_all_users(self, course):
		"""
		Retrieve all user IDs in both "master" and course databases

		:return: all user IDs in master database, all user IDs in course database
		:rtype:  tuple (string arrays)
		"""

		self.MASTER_DB_CUR.execute("SELECT user_id FROM courses_users where course='{}' and semester='{}'".format(course, self.SEMESTER))
		masterdb_users = tuple(row[0] for row in self.MASTER_DB_CUR.fetchall())

		self.COURSE_DB_CUR.execute("SELECT user_id FROM users")
		coursedb_users = tuple(row[0] for row in self.COURSE_DB_CUR.fetchall())

		return masterdb_users, coursedb_users

# ------------------------------------------------------------------------------

	def reconcile_master_course(self, user_list):
		"""master DB user data overrides course DB user data"""

		# Retrieve data from "master" DB
		# user_id is primary key (unique record identifier), so there should be only one row per query.
		for user_id in user_list:
			try:
				self.MASTER_DB_CUR.execute("SELECT user_firstname, user_preferred_firstname, user_lastname, user_email FROM users where user_id='{}'".format(user_id))
				row = list(self.MASTER_DB_CUR.fetchone())
				self.MASTER_DB_CUR.execute("SELECT user_group, registration_section, manual_registration from courses_users")
				row.extend(self.MASTER_DB_CUR.fetchone())
				self.COURSE_DB_CUR.execute("UPDATE users SET user_firstname='{}', user_preferred_firstname='{}', user_lastname='{}', user_email='{}', user_group='{}', registration_section='{}', manual_registration='{}' where user_id='{}'".format(*row, user_id))
			except:
				return False

		return True

# ------------------------------------------------------------------------------

	def determine_semester(self):
		"""
		Build/return semester string.  e.g. "s17" for Spring 2017.
		:return: The semester string
		:rtype:  string
		"""

		today = datetime.date.today()
		month = today.month
		year  = str(today.year % 100)
		# if month <= 5: ... elif month >=8: ... else: ...
		return 's' + year if month <= 5 else ('f' + year if month >= 8 else 'm' + year)

# ------------------------------------------------------------------------------

	def print_help(self):
		"""Print help message to STDOUT/console"""

		# Clear console
		os.system('cls' if os.name == 'nt' else 'clear')
		print("Usage: db_sync.py (help | all | course...)\n");
		print("Command line tool to sync course databases with master submitty database.\n")
		print("help:   This help message")
		print("all:    Sync all course databases")
		print("course: Specific course or list of courses to sync\n")
		print("EXAMPLES:")
		print("db_sync.py all")
		print("Sync ALL courses with master submitty database.\n")
		print("db_sync.py csci1100")
		print("Sync course csci1100 with master submitty databse.\n")
		print("db_sync.py csci1200 csci2200 csci3200")
		print("Sync courses csci1200, csci2200, and csci3200 with master submitty database.\n")

# ------------------------------------------------------------------------------

if __name__ == "__main__":
	db_sync()

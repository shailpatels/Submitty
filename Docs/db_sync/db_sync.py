#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import datetime
import os
import psycopg2
import sys

# CONFIGURATION ----------------------------------------------------------------
DB_HOST = 'localhost'
DB_USER = 'hsdbu'
DB_PASS = 'hsdbu'
# ------------------------------------------------------------------------------

class db_sync:
	"""Sync user data in course databases with master database"""

	def __init__(self):
		"""Main process"""

		if len(sys.argv) < 2 or sys.argv[1] == 'help':
			self.print_help()
			sys.exit(0)
		elif sys.argv[1] == 'all':
			# Get complete course list
			self.db_connect()
			course_list = self.get_all_courses()
		else:
			# Validate that courses exist
			self.db_connect()
			course_list = [course for course in sys.argv[1:] if course in self.get_all_courses()]
			invalid_course_list = [course for course in sys.argv[1:] if course not in course_list]

			# Check that invalidated_course_list is not empty
			if invalidated_course_list:
				# Get user permission to proceed
				if not self.print_invalid_courses(invalid_course_list):
					sys.exit(0)

		# Process database sync
		# Exit

# ------------------------------------------------------------------------------

	def db_connect(self):
		"""Establish connection to Submitty Master DB"""

		try:
			self.DB_CONN = psycopg2.connect("dbname='submitty' user={} host={} password={}".format(DB_USER, DB_HOST, DB_PASS))
		except:
			raise SystemExit("ERROR: Cannot connect to Submitty master database")

# ------------------------------------------------------------------------------

	def get_all_courses(self):
		"""
		Retrieve active course list from Master DB
		:return: list of all active courses
		:rtype: list (string)
		"""

		db_cur = self.DB_CONN.cursor()
		db_cur.execute("SELECT course FROM courses WHERE semester='{}'".format(self.determine_semester()))
		return [row[0] for row in db_cur.fetchall()]

# ------------------------------------------------------------------------------

	def determine_semester(self):
		"""
		Build/return semester string.  e.g. "s17" for Spring 2017.
		:return: The semester string
		:rtype: string
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
		os.system('cls' if os.name=='nt' else 'clear')
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

	def print_invalid_courses(self, invalidated_courses):
		"""
		When invalid courses are specified, query user to continue processing valid courses

		:param invalidated_courses: list of user specified courses that do not exist in Master DB
		:type invalidated_courses: list (strings)
		:return: true when user gives permission to proceed, false otherwise
		:rtype: boolean
		"""

		# Clear console
		os.system('cls' if os.name=='nt' else 'clear')

# ------------------------------------------------------------------------------

if __name__ == "__main__":
	db_sync()


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

def main(argv):
	if len(argv) < 2 or argv[1] == 'help':
		print_help()
		sys.exit(0)
	elif argv[1] == 'all':
		# Connect to DB
		db_conn = db_connect()
		# Get complete course list
		course_list = get_all_courses(db_conn)
	else:
		# Connect to DB
		db_conn = db_connect()
		# Validate list
		# If validated, sync all courses in list

	# Process database sync
	# Exit


def db_connect():
	try:
		return psycopg2.connect("dbname='submitty' user={} host={} password={}".format(DB_USER, DB_HOST, DB_PASS))
	except:
		raise SystemExit("ERROR: Cannot connect to Submitty master database")

def get_all_courses(db_conn):
	db_cur = db_conn.cursor()
	db_cur.execute("SELECT course FROM courses WHERE semester='{}'".format(determine_semester()))
	return [course for row in db_cur.fetchall() for course in row]

def determine_semester():
	today = datetime.date.today()
	month = today.month
	year  = str(today.year % 100)
	# if month <= 5: ... elif month >=8: ... else: ...
	return 's' + year if month <= 5 else ('f' + year if month >= 8 else 'm' + year)

def print_help():
	"""
	Print help message to STDOUT/console
	"""
	os.system('clear')
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

if __name__ == "__main__":
	main(sys.argv)
	sys.exit(0)

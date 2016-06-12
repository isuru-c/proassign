import sys
import subprocess
import MySQLdb

if(len(sys.argv) < 2):
	print("Not enough data given")
	exit()

# get data from arguments
	
file_name = sys.argv[1]	
proassign_id = sys.argv[2]
submission_id = sys.argv[3]

# create the database connection

db = MySQLdb.connect("localhost","root","ic","moodle" )
cursor = db.cursor()

sql = "SELECT * FROM mdl_proassign WHERE id=" + proassign_id
cursor.execute(sql)

pro_data = cursor.fetchone()

sql = "SELECT * FROM mdl_proassign_grades WHERE submission=" + submission_id
cursor.execute(sql)

grade_data = cursor.fetchone()

if grade_data is None:
	# first time grading
	state = "1"
	sql = "INSERT INTO mdl_proassign_grades (proassign, submission, state) VALUES (" + proassign_id + ", " + submission_id + ", '" + state + "')"
	print sql
	try:
		cursor.execute(sql)
   		db.commit()
	except: db.rollback()
else:
	# has tried before, so change the existing state
	state = "2"
	sql = "UPDATE mdl_proassign_grades SET state='" + state + "' WHERE submission=" + submission_id
	try:
		cursor.execute(sql)
   		db.commit()
	except: db.rollback()
		
in_list = []
count = 0;
		
if pro_data[11] == 1:
	tmp_list = pro_data[12].split("\r\n")
	in_list.append(tmp_list)		
if pro_data[16] == 1:
	tmp_list = pro_data[17].split("\r\n")
	in_list.append(tmp_list)		
if pro_data[21] == 1:
	tmp_list = pro_data[22].split("\r\n")
	in_list.append(tmp_list)

# creating the input list for testing is completed
	
path = "/var/www/html/moodle/mod/proassign/runner/codes/" + file_name
	
count = len(in_list)	
i = 0	
	
pipe = subprocess.Popen(["python", path], stdout=subprocess.PIPE, stdin=subprocess.PIPE)
for test_input in in_list[i]:
	print test_input
	pipe.stdin.write(test_input)
#out, err = p.communicate()

print out

db.close()

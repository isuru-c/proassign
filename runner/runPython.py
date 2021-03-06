import sys
import subprocess
import MySQLdb
import time

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
ex_list = []
		
if pro_data[11] == 1:
	in_list.append(pro_data[12])
	ex_list.append(pro_data[13])		
if pro_data[16] == 1:
	in_list.append(pro_data[17])
	ex_list.append(pro_data[18])		
if pro_data[21] == 1:
	in_list.append(pro_data[22])
	ex_list.append(pro_data[23])

# creating the input list for testing is completed
	
#file_name = "one.py"	
	
path = "/var/www/html/moodle/mod/proassign/runner/codes/" + file_name
	
out_list = []
err_list = []

for input_data in in_list:
	try:	
		pipe = subprocess.Popen(["python", path],  stdout=subprocess.PIPE, stdin=subprocess.PIPE, stderr=subprocess.PIPE)
		pipe.stdin.write(input_data)
		out, err = pipe.communicate()
		out = out[:len(out)-1]
		out_list.append(out)
		err_list.append(err)
	except:
		print sys.exc_info()

		

gra_list = []

for k in range(len(ex_list)):
	grade = pro_data[15+k*5]
	x = ex_list[0].split('\r\n')
	y = out_list[0].split('\n')

	if len(x) == len(y):
		for i in range(len(x)):
			if x[i] != y[i]:
				grade = 0
				break;
	else:
		grade = 0
	gra_list.append(grade)
		
		
		
state = "3"
sql = "UPDATE mdl_proassign_grades SET output1='" + out_list[0] + "', output2='" + out_list[1] + "', output3='" + out_list[2] + "'" 
sql += ", error1='" + err_list[0] + "', error2='" + err_list[1] + "', error3='" + err_list[2] + "'"
sql += ", grade1=" + str(gra_list[0]) + ", grade2=" + str(gra_list[1]) + ", grade3=" + str(gra_list[2])
sql += ", state='" + state + "', timegraded=" + str(int(time.time())) + " WHERE submission=" + submission_id
	
try:
	cursor.execute(sql)
	db.commit()
except: db.rollback()
		
db.close()

#print ex_list
#print out_list[0]

#for x in out_list[0]: print x
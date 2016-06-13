import sys
import subprocess
import MySQLdb

if(len(sys.argv) < 2):
	print("Not enough data given")
	exit()

# get data from arguments
	
file_name = sys.argv[1]	
proassign_id = sys.argv[2]
	
db = MySQLdb.connect("localhost","root","ic","moodle" )
cursor = db.cursor()

sql = "SELECT * FROM mdl_proassign WHERE id=" + proassign_id
cursor.execute(sql)

pro_data = cursor.fetchone()	

db.close()
	
in_list = []
		
if pro_data[11] == 1 and pro_data[14] == 1:
	in_list.append(pro_data[12])		
if pro_data[16] == 1 and pro_data[19] == 1:
	in_list.append(pro_data[17])		
if pro_data[21] == 1 and pro_data[24] == 1:
	in_list.append(pro_data[22])

# creating the input list for testing is completed
	
path = "/var/www/html/moodle/mod/proassign/runner/codes/" + file_name
	
out_list = []

pipe = subprocess.Popen(["python", path],  stdout=subprocess.PIPE, stdin=subprocess.PIPE)
out, err = pipe.communicate()
out = out[:len(out)-1]
out_list.append(out)

print out_list

exit()

for input_data in in_list:
	try:	
		pipe = subprocess.Popen(["python", path],  stdout=subprocess.PIPE, stdin=subprocess.PIPE)
		pipe.stdin.write(input_data)
		out, err = pipe.communicate()
		out = out[:len(out)-1]
		out_list.append(out)
	except:
		print sys.exc_info()

print out_list		


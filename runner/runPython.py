import sys
import subprocess
import MySQLdb

if(len(sys.argv) < 2):
	print("Not enough data given")
	exit()
	
file_name = sys.argv[1]	
proassign_id = sys.argv[2]

# create the database connection

db = MySQLdb.connect("localhost","root","ic","moodle" )
cursor = db.cursor()

sql = "SELECT * FROM mdl_proassign WHERE id=" + proassign_id
cursor.execute(sql)

data = cursor.fetchone()

# everything is ready to run the file, send ok msg to web server and start grading

print ("ok")

#res = call(["python", "/var/www/html/moodle/mod/proassign/runner/codes/"+file_name, "5"])

#res = subprocess.call(["python", "test.py"])


p = subprocess.Popen(["python", "test2.py"], stdout=subprocess.PIPE, stdin=subprocess.PIPE)
p.stdin.write("3")
out, err = p.communicate()

print out

db.close()
import sys
import MySQLdb

if(len(sys.argv) < 2):
	print("Not enough data given")
	exit()
	
submission_id = sys.argv[1]

# create the database connection

db = MySQLdb.connect("localhost","root","ic","moodle" )
cursor = db.cursor()

sql = "SELECT * FROM mdl_proassign_submission WHERE id=" + submission_id
cursor.execute(sql)

data = cursor.fetchone()

proassingn_id = data[1]
text_submission = data[4]

if text_submission==0:
	print ("No text submission, can not grade the submission")
	exit()
	
text_data = data[5]

# save the code in a file, so it can be executed

file_name = "codes/tmp_" + submission_id + ".py"

fo = open(file_name, "w")
fo.write(text_data)

fo.close()

print (data[5])


db.close()
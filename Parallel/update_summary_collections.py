from pymongo import MongoClient

client = MongoClient("mongodb://localhost:27017/")
db = client["CSELEC3DB"]

def update_class_averages():
    print("📊 Updating class_averages...")
    pipeline = [
        {"$unwind": "$Grades"},
        {"$unwind": "$SubjectCodes"},
        {"$group": {
            "_id": {
                "subject_code": "$SubjectCodes",
                "semester_id": "$SemesterID"
            },
            "average_grade": {"$avg": "$Grades"},
            "passing_rate": {"$avg": {"$cond": [{"$gte": ["$Grades", 75]}, 1, 0]}},
            "at_risk_rate": {"$avg": {"$cond": [{"$lt": ["$Grades", 75]}, 1, 0]}},
            "top_grade": {"$max": "$Grades"}
        }},
        {"$lookup": {
            "from": "subjects",
            "localField": "_id.subject_code",
            "foreignField": "_id",
            "as": "subject_info"
        }},
        {"$unwind": "$subject_info"},
        {"$project": {
            "_id": 0,
            "subject_code": "$_id.subject_code",
            "semester_id": "$_id.semester_id",
            "average_grade": 1,
            "passing_rate": 1,
            "at_risk_rate": 1,
            "top_grade": 1,
            "subject_description": "$subject_info.Description"
        }}
    ]
    db.class_averages.drop()
    db.class_averages.insert_many(db.grades.aggregate(pipeline))
    print("✅ Done class_averages.")

def update_semester_metrics():
    print("📚 Updating semester_metrics...")
    pipeline = [
        {"$unwind": "$Grades"},
        {"$group": {
            "_id": "$SemesterID",
            "average_grade": {"$avg": "$Grades"},
            "passing_rate": {"$avg": {"$cond": [{"$gte": ["$Grades", 75]}, 1, 0]}},
            "at_risk_rate": {"$avg": {"$cond": [{"$lt": ["$Grades", 75]}, 1, 0]}},
            "top_grade": {"$max": "$Grades"}
        }},
        {"$project": {
            "_id": 0,
            "semester_id": "$_id",
            "average_grade": 1,
            "passing_rate": 1,
            "at_risk_rate": 1,
            "top_grade": 1
        }}
    ]
    db.semester_metrics.drop()
    db.semester_metrics.insert_many(db.grades.aggregate(pipeline))
    print("✅ Done semester_metrics.")

def update_student_gpas():
    print("👨‍🎓 Updating student_gpas...")
    pipeline = [
        {"$unwind": {"path": "$SubjectCodes", "includeArrayIndex": "i"}},
        {"$unwind": {"path": "$Grades", "includeArrayIndex": "j"}},
        {"$match": {"$expr": {"$eq": ["$i", "$j"]}}},
        {"$lookup": {
            "from": "subjects",
            "localField": "SubjectCodes",
            "foreignField": "_id",
            "as": "subject"
        }},
        {"$unwind": "$subject"},
        {"$project": {
            "StudentID": 1,
            "SemesterID": 1,
            "grade": "$Grades",
            "units": "$subject.Units"
        }},
        {"$group": {
            "_id": {"student_id": "$StudentID", "semester_id": "$SemesterID"},
            "grades": {"$push": "$grade"},
            "units": {"$push": "$units"}
        }},
        {"$project": {
            "student_id": "$_id.student_id",
            "semester_id": "$_id.semester_id",
            "weighted_sum": {
                "$sum": {
                    "$map": {
                        "input": {"$range": [0, {"$size": "$grades"}]},
                        "as": "i",
                        "in": {
                            "$multiply": [
                                {"$arrayElemAt": ["$grades", "$$i"]},
                                {"$arrayElemAt": ["$units", "$$i"]}
                            ]
                        }
                    }
                }
            },
            "total_units": {"$sum": "$units"}
        }},
        {"$addFields": {
            "weighted_average": {"$cond": [
                {"$gt": ["$total_units", 0]},
                {"$divide": ["$weighted_sum", "$total_units"]},
                0
            ]}
        }},
        {"$group": {
            "_id": "$student_id",
            "weighted_average": {"$avg": "$weighted_average"}
        }},
        {"$project": {
            "_id": 0,
            "student_id": "$_id",
            "weighted_average": {"$round": ["$weighted_average", 2]}
        }}
    ]
    db.student_gpas.drop()
    db.student_gpas.insert_many(db.grades.aggregate(pipeline))
    print("✅ Done student_gpas.")

# Run all
update_class_averages()
update_semester_metrics()
update_student_gpas()

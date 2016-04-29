select  Dbo_classrooms.ROOMNO,
    Dbo_classrooms.NO,
    Dbo_classrooms.JSN,
    Dbo_classrooms.BUILDING,
    Dbo_classrooms.AREA,
    Dbo_classrooms.SEATS,
    Dbo_classrooms.TESTERS,
    Dbo_classrooms.EQUIPMENT,
    Dbo_classrooms.STATUS,
    Dbo_classrooms.PRIORITY,
    Dbo_classrooms.USAGE,
    Dbo_classrooms.RESERVED,
    Dbo_classrooms.ROOM_AREA,
    Dbo_areas.VALUE AS AREAVALUE,
    Dbo_roomoptions.VALUE AS EQUIPMENTVALUE,
    Dbo_roomusages.VALUE AS USAGEVALUE,
    Dbo_schools.NAME AS SCHOOLNAME,
    Dbo_classrooms.REM
FROM dbo.CLASSROOMS Dbo_classrooms
    left join dbo.AREAS Dbo_areas on Dbo_classrooms.AREA = Dbo_areas.NAME
    left join dbo.ROOMOPTIONS Dbo_roomoptions on Dbo_classrooms.EQUIPMENT = Dbo_roomoptions.NAME
    left join dbo.ROOMUSAGES Dbo_roomusages on Dbo_classrooms.USAGE = Dbo_roomusages.NAME
    left join dbo.SCHOOLS Dbo_schools on Dbo_classrooms.PRIORITY = Dbo_schools.SCHOOL
WHERE Dbo_classrooms.ROOMNO =:ROOMNO


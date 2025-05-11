<?php
return [
    'nlp' => [
        'enabled' => true,
        'provider' => 'local',
        'confidence_threshold' => 0.7
    ],
    'context' => [
        'max_history' => 5,
        'timeout' => 3600 // 1 hour
    ],
    'suggestions' => [
        'max_count' => 3,
        'min_confidence' => 0.6
    ],
    'database' => [
        'tables' => [
            'employees' => [
                'name' => 'employees',
                'columns' => ['id', 'name', 'email', 'phone', 'department_id', 'position_id', 'status'],
                'relations' => [
                    'departments' => ['type' => 'belongs_to', 'foreign_key' => 'department_id'],
                    'positions' => ['type' => 'belongs_to', 'foreign_key' => 'position_id']
                ]
            ],
            'departments' => [
                'name' => 'departments',
                'columns' => ['id', 'name', 'description', 'manager_id'],
                'relations' => [
                    'employees' => ['type' => 'has_many', 'foreign_key' => 'department_id']
                ]
            ],
            'salaries' => [
                'name' => 'salaries',
                'columns' => ['id', 'employee_id', 'amount', 'effective_date', 'type'],
                'relations' => [
                    'employees' => ['type' => 'belongs_to', 'foreign_key' => 'employee_id']
                ]
            ]
        ]
    ],
    'intents' => [
        'employee_info' => [
            'patterns' => [
                'thông tin nhân viên',
                'hồ sơ nhân viên',
                'profile nhân viên',
                'chi tiết nhân viên'
            ],
            'entities' => ['employee_name', 'employee_id', 'department'],
            'required_tables' => ['employees', 'departments', 'positions']
        ],
        'salary_info' => [
            'patterns' => [
                'lương nhân viên',
                'thông tin lương',
                'bảng lương',
                'lương tháng'
            ],
            'entities' => ['employee_name', 'month', 'year'],
            'required_tables' => ['salaries', 'employees']
        ],
        'department_info' => [
            'patterns' => [
                'thông tin phòng ban',
                'danh sách phòng ban',
                'cơ cấu phòng ban'
            ],
            'entities' => ['department_name'],
            'required_tables' => ['departments', 'employees']
        ]
    ]
]; 
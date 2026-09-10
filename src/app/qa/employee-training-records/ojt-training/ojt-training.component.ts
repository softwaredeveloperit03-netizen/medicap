import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { defaultFromDate, defaultToDate, getEmpDisplayName, hasStamp } from '../training-records.utils';

declare let alertify: any;

interface TrainingStep {
  content: string;
  trainee_stamp: string;
  trainer_stamp: string;
  trainer_na: boolean;
}

@Component({
  selector: 'app-ojt-training',
  templateUrl: './ojt-training.component.html',
  styleUrls: ['../training-records.shared.css'],
  providers: [DatePipe],
})
export class OjtTrainingComponent implements OnInit {
  formNo = 'FQA-003-D';
  revisionNo = '00';
  effectiveDate = '2025-03-24';
  isLog = false;
  isView = false;
  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';
  stamping: string | null = null;

  employees: any[] = [];
  selectedEmployee: any = null;
  employeeName = '';
  employeeId = '';
  employeeSignature = '';
  position = '';
  department = '';
  contentOfTraining = '';
  relatedSop = '';
  equipmentId = '';
  referenceDocument = '';
  otherSpecify = '';
  adequateKnowledge = '';
  areasOfConcern = '';
  assessmentComment = '';

  trainingSteps: TrainingStep[] = [
    { content: 'Trainee read and Understand SOP/ Work instruction', trainee_stamp: '', trainer_stamp: 'N/A', trainer_na: true },
    { content: 'Trainee shadow trainer throughout the work', trainee_stamp: '', trainer_stamp: '', trainer_na: false },
    { content: 'Trainee perform task under supervision of trainer', trainee_stamp: '', trainer_stamp: '', trainer_na: false },
    { content: 'Trainee perform task independently', trainee_stamp: '', trainer_stamp: '', trainer_na: false },
  ];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
  }

  ngOnInit(): void {
    this.isLog = this.route.snapshot.data['view'] === 'log';
    if (this.isLog) {
      this.getLog();
    } else {
      this.getEmployees();
    }
  }

  getEmployees(): void {
    this.service.get('employee.php?type=getEmployees').subscribe(
      (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        if (!this.employees.length) {
          this.loadEmployeesFallback();
        }
      },
      () => this.loadEmployeesFallback()
    );
  }

  loadEmployeesFallback(): void {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment').subscribe(
      (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        if (!this.employees.length) {
          this.service.get('common.php?type=getEmployees').subscribe((res: any) => {
            this.employees = Array.isArray(res) ? res : [];
          });
        }
      },
      () => {
        this.service.get('common.php?type=getEmployees').subscribe((res: any) => {
          this.employees = Array.isArray(res) ? res : [];
        });
      }
    );
  }

  getEmployeeLabel(emp: any): string {
    const name = [emp?.firstname, emp?.middlename, emp?.lastname].filter(Boolean).join(' ').trim();
    return (name || emp?.emp_id || '') + (emp?.emp_id ? ' (' + emp.emp_id + ')' : '');
  }

  onEmployeeChange(): void {
    if (!this.selectedEmployee) {
      this.employeeName = '';
      this.employeeId = '';
      this.position = '';
      this.department = '';
      return;
    }

    this.employeeId = this.selectedEmployee.emp_id || '';
    this.employeeName = [this.selectedEmployee.firstname, this.selectedEmployee.middlename, this.selectedEmployee.lastname]
      .filter(Boolean)
      .join(' ')
      .trim();
    this.position = this.selectedEmployee.designation || '';
    this.department = this.selectedEmployee.department || '';

    if (this.employeeId) {
      this.loadPreviousTraining(this.employeeId);
    }
  }

  loadPreviousTraining(empId: string): void {
    const params =
      'emp_id=' + encodeURIComponent(empId) + '&employee_name=' + encodeURIComponent(this.employeeName);
    this.service
      .get('qa/employeeTrainingRecords.php?type=getOjtTrainingByEmployee&' + params)
      .subscribe((response: any) => {
        if (!response || !response.id) {
          return;
        }
        if (response.position) this.position = response.position;
        if (response.department) this.department = response.department;
        if (response.content_of_training) this.contentOfTraining = response.content_of_training;
        if (response.related_sop) this.relatedSop = response.related_sop;
        if (response.equipment_id) this.equipmentId = response.equipment_id;
        if (response.reference_document) this.referenceDocument = response.reference_document;
      });
  }

  stampEmployeeSignature(): void {
    this.employeeSignature = getEmpDisplayName() + ' - ' + this.datePipe.transform(new Date(), 'dd-MM-yyyy HH:mm');
  }

  getLog(): void {
    this.service
      .get('qa/employeeTrainingRecords.php?type=getOjtTrainingLog&from_date=' + this.fromDate + '&to_date=' + this.toDate)
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
  }

  save(form: NgForm): void {
    if (form.invalid || !this.selectedEmployee || !this.employeeName.trim()) {
      alertify.error('Please select employee and fill required fields');
      return;
    }
    const payload = {
      employee_id: this.employeeId,
      employee_name: this.employeeName,
      employee_signature: this.employeeSignature,
      position: this.position,
      department: this.department,
      content_of_training: this.contentOfTraining,
      related_sop: this.relatedSop,
      equipment_id: this.equipmentId,
      reference_document: this.referenceDocument,
      training_steps: this.trainingSteps,
      other_specify: this.otherSpecify,
      adequate_knowledge: this.adequateKnowledge,
      areas_of_concern: this.areasOfConcern,
      assessment_comment: this.assessmentComment,
      reviewed_by: '',
      reviewed_date: '',
    };
    this.service
      .post('qa/employeeTrainingRecords.php?type=saveOjtTraining', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Record saved successfully');
          this.router.navigate(['/qa/employee-training-records/ojt/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  stampStep(record: any, rowIndex: number, field: 'trainee' | 'trainer'): void {
    const step = record.training_steps?.[rowIndex];
    if (!step || step.trainer_na && field === 'trainer') return;
    const stampField = field === 'trainer' ? 'trainer_stamp' : 'trainee_stamp';
    const key = record.id + '-' + rowIndex + '-' + field;
    if (hasStamp(step[stampField]) || this.stamping === key) return;

    this.stamping = key;
    this.service
      .post(
        'qa/employeeTrainingRecords.php?type=updateOjtTrainingStamp',
        JSON.stringify({ id: record.id, stamp_type: 'step', row_index: rowIndex, field })
      )
      .subscribe(
        (response: any) => {
          this.stamping = null;
          if (response?.status === 'success') {
            record.training_steps = response.training_steps;
            if (this.selectedRecord?.id === record.id) this.selectedRecord.training_steps = response.training_steps;
            alertify.success('Stamp saved.');
          } else {
            alertify.error(response?.status || 'Failed to stamp');
          }
        },
        () => {
          this.stamping = null;
          alertify.error('Failed to stamp');
        }
      );
  }

  stampReviewed(record: any): void {
    if (hasStamp(record.reviewed_by) || this.stamping === record.id + '-reviewed') return;
    this.stamping = record.id + '-reviewed';
    this.service
      .post('qa/employeeTrainingRecords.php?type=updateOjtTrainingStamp', JSON.stringify({ id: record.id, stamp_type: 'reviewed' }))
      .subscribe(
        (response: any) => {
          this.stamping = null;
          if (response?.status === 'success') {
            record.reviewed_by = response.value;
            record.reviewed_date = this.datePipe.transform(new Date(), 'yyyy-MM-dd');
            if (this.selectedRecord?.id === record.id) {
              this.selectedRecord.reviewed_by = response.value;
              this.selectedRecord.reviewed_date = record.reviewed_date;
            }
            alertify.success('Review saved.');
          } else {
            alertify.error(response?.status || 'Failed to stamp');
          }
        },
        () => {
          this.stamping = null;
          alertify.error('Failed to stamp');
        }
      );
  }

  hasStamp = hasStamp;

  downloadForm(id: number): void {
    this.service.open('qa/employeeTrainingRecords.php?type=downloadOjtTrainingForm&id=' + id);
  }
}

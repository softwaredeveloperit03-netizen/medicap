import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-depreview',
  templateUrl: './depreview.component.html',
  styleUrls: ['./depreview.component.css'],
})
export class DepreviewComponent implements OnInit {
  isView = false;
  results;
  selectedRisk = [];
  assessment = [];
  analysis = [];
  evaluation = [];
  risks;
  risk = [];
  isRisk = false;
  justification = '';
  departments;
  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingEvaluation();
    this.getDepartments();
  }
  getDepartments() {
    this.departments = [
      { department_name: 'Store', value: false },
      { department_name: 'Production', value: false },
      { department_name: 'Quality Control', value: false },
      { department_name: 'Packing', value: false },
      { department_name: 'Marketing', value: false },
      { department_name: 'Client', value: false },
      { department_name: 'Regulatory Department', value: false },
      { department_name: 'Management', value: false },
      { department_name: 'Human Resource', value: false },
      { department_name: 'Engineering', value: false },
    ];
  }
  updateDept(value, i) {
    this.departments[i].status = value;
  }
  getPendingEvaluation() {
    this.service
      .get('qa/risk.php?type=getControlDone')
      .subscribe((response) => {
        this.results = response;
      });
  }


  viewRisk(index) {
    this.selectedRisk = this.results[index];

    let assessment = this.selectedRisk['assessment_details'];
    this.assessment = assessment[0];
    console.log('this.assessment =', this.assessment);

    let analysis = this.selectedRisk['analysis_details'];
    this.analysis = analysis[0];
    console.log('this.analysis =', this.analysis);

    let evaluation = this.selectedRisk['evaluation_details'];
    this.evaluation = evaluation[0];
    console.log('this.evaluation =', this.evaluation);
    this.isView = true;
  }
    saveControl() {
      let temp = {};
   let selectedDepartments = [];
      let test = [];
      for (let i = 0; i < this.departments.length; i++) {
        let department = this.departments[i];
        if (department['status']) {
          test[test.length] = department['department_name'];
        }
      }
      temp['departments'] = test;
       temp['selectedDeptCount'] = selectedDepartments.length;
      this.service.post('qa/risk.php?type=saveDepReview&id=' + this.selectedRisk["id"],JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.isView = false;
          this.getPendingEvaluation();
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }
  
  }


import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'qa-qms-deviation', title: 'Initiate Deviation', route: 'qa/qms/deviation', icon: 'fa-pencil-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'qa-qms-deviation-riskassessment', title: 'Risk Assessment', route: 'qa/qms/deviation/riskassessment', icon: 'fa-audit', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'condition', title: 'Conditl & Final Apprvl', route: 'condition', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'newlog', title: 'Deviations Log', route: 'newlog', icon: 'fa-exclamation-triangle', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'initiate', title: 'Initiate Deviation', route: 'initiate', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'attachment', title: 'Upload Attachments', route: 'attachment', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'checking', title: 'Deviation for Department checking', route: 'checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'verify', title: 'Deviation Verify by QA', route: 'verify', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'approval', title: 'Deviation Approval by QA Head', route: 'approval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'investigation', title: 'Investigation Details', route: 'investigation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'assessment', title: 'Assessment by QA', route: 'assessment', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'action-plan', title: 'Action Plan', route: 'action_plan', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'recommendation', title: 'Final Recomm. by QA Head', route: 'recommendation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'evaluation', title: 'Evaluation by  QA', route: 'evaluation', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'closing', title: 'Closure by QA', route: 'closing', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'log', title: 'Deviations Log', route: 'log', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
  ];


  isUser = false;
  isChecker = false;
  isApprover = false;

  // constructor() {
  //   this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
  //   this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
  //   this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  // }
  constructor(private service:DataAccessService) { }


  ngOnInit() {
    this.get_rights();

  }
  rights;
  righ;
  ischecker
isapprover
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Quality Control &form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      console.log(this.rights)
      console.log(this.righ)
    });
  }

}

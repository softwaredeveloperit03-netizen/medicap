import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'oinitiate', title: 'Initiate CC', route: 'oinitiate', icon: 'fa-file', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'ochecking', title: 'CC for Dept Checkg', route: 'Ochecking', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'opreapproval', title: 'Pre Approval Of CC', route: 'opreapproval', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'ocustomer', title: 'QA Review', route: 'ocustomer', icon: 'fa-user-check', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'oprechecking', title: 'Pre Aprvl Checkg', route: 'oprechecking', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'review', title: 'Regulatory Dep', route: 'review', icon: 'fa-file-alt', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'postapproval', title: 'Post Apprvl of CC', route: 'postapproval', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'opostapproval1', title: 'Final QA Review', route: 'opostapproval1', icon: 'fa-user-check', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'extension', title: 'Extension of CC', route: 'extension', icon: 'fa-calendar-plus', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'extapproval', title: 'Extension Approval', route: 'extapproval', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'oadditional', title: 'Post Implet. Review', route: 'oadditional', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'postimplementation', title: 'Post Implementation', route: 'postImplementation', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'closing', title: 'Closure of CC', route: 'closing', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'closing1', title: 'Closure of CC Apprvl', route: 'closing1', icon: 'fa-clipboard-check', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'log', title: 'Logbook', route: 'log', icon: 'fa-book', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'initiate', title: 'Initiate CC', route: 'initiate', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'attachment', title: 'Upload Attachments', route: 'attachment', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'checking', title: 'CC for Department Checking', route: 'checking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
    { id: 'ochecking', title: 'CC for Department Checking', route: 'Ochecking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #a1c4fd 0%, #764ba2 100%)' },
    { id: 'preapproval', title: 'Pre Approval Of CC', route: 'preapproval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #764ba2 0%, #764ba2 100%)' },
    { id: 'customer', title: 'Pre Approval from Customer', route: 'customer', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'prechecking', title: 'Pre Approval Checking', route: 'prechecking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'oprechecking', title: 'Pre Approval Checking', route: 'oprechecking', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'review', title: 'Concerned Dept. Review', route: 'review', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'postapproval', title: 'Post Approval of CC', route: 'postapproval', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'postapproval1', title: 'Post Approval of CC by CQA', route: 'postapproval1', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'additional', title: 'Post Implementation Review', route: 'additional', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'oadditional', title: 'Post Implement Review', route: 'oadditional', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
  ];

  plant_id;
  constructor(private service: DataAccessService) { 
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }

  ngOnInit(): void {
    this.get_rights();

  }
  rights;
  righ;
  ischecker;
isapprover;
qms_approver;
  get_rights() {
    this.service.get('hr/employee.php?type=getrights&module_name=Quotation&department1=Quality Assurance&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.righ=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      console.log(this.rights)
      console.log(this.righ)
      console.log(this.qms_approver)
    });
  }

}

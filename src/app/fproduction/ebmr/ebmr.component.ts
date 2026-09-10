import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-ebmr',
  templateUrl: './ebmr.component.html',
})
export class EbmrComponent implements OnInit {
  cards: QcDeptCard[] = [];

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
    this.buildCards();
  }

  private buildCards(): void {
    const base = '/fproduction/ebmr';
    this.cards = [
      {
        id: 'start-production',
        title: 'Start Production',
        route: `${base}/start`,
        icon: 'fa-play-circle',
        category: 'Production Batch',
        gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
      },
      {
        id: 'work-allocation',
        title: 'Work Allocation',
        route: `${base}/work-allocation`,
        icon: 'fa-tasks',
        category: 'Production Batch',
        gradient: 'linear-gradient(135deg, #6a11cb 0%, #2575fc 100%)',
      },
      {
        id: 'under-production-batches',
        title: 'Under Production (eBMR)',
        route: `${base}/under-production`,
        icon: 'fa-industry',
        category: 'Production Batch',
        gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
      },
      {
        id: 'bmr-prep',
        title: 'BMR Master Preparation',
        route: `${base}/bmr-prep`,
        icon: 'fa-file-medical',
        category: 'eBMR Master',
        gradient: 'linear-gradient(135deg, #0b4f6c 0%, #08607f 100%)',
      },
      {
        id: 'profiles',
        title: 'eBMR / eBPR Profiles',
        route: `${base}/profiles`,
        icon: 'fa-box-open',
        category: 'eBMR Master',
        gradient: 'linear-gradient(135deg, #08607f 0%, #0e7490 100%)',
      },
      {
        id: 'reports',
        title: 'Batch Records & Reports',
        route: `${base}/reports`,
        icon: 'fa-file-invoice',
        category: 'Reports',
        gradient: 'linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%)',
      },
      {
        id: 'completed-bmr',
        title: 'Completed BMR',
        route: `${base}/completed`,
        icon: 'fa-check-double',
        category: 'Reports',
        gradient: 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
      },
      {
        id: 'bmr-audit-trail',
        title: 'BMR Audit Trail',
        route: `${base}/audit-trail`,
        icon: 'fa-clipboard-list',
        category: 'Compliance',
        gradient: 'linear-gradient(135deg, #0b4f6c 0%, #134e4a 100%)',
      },
    ];
  }

  rights: any;
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  trainig_cordinator = 'No';
  loggedInDept: string | null = null;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          (this.loggedInDept || '')
      )
      .subscribe((response: any) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
        this.trainig_cordinator = this.rights[0].trainig_cordinator || 'No';
      });
  }
}

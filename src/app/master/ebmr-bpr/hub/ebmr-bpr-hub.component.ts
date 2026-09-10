import { Component, OnInit } from '@angular/core';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { MasterHubReturnService } from '../../master-hub-return.service';

@Component({
  selector: 'app-ebmr-bpr-hub',
  templateUrl: './ebmr-bpr-hub.component.html',
  styleUrls: ['./ebmr-bpr-hub.component.css'],
})
export class EbmrBprHubComponent implements OnInit {
  readonly deptId = 'process-stage';

  cards: QcDeptCard[] = [
    {
      id: 'prod-master',
      title: 'Production eBMR Master',
      route: 'product-master/production',
      icon: 'fa-industry',
      category: 'Master Setup',
      gradient: 'linear-gradient(135deg, #0e4370 0%, #1a5f9e 100%)',
    },
    {
      id: 'pack-master',
      title: 'Packing BPR Master',
      route: 'product-master/packing',
      icon: 'fa-box-open',
      category: 'Master Setup',
      gradient: 'linear-gradient(135deg, #0f766e 0%, #14b8a6 100%)',
    },
    {
      id: 'configure',
      title: 'Configure BMR',
      route: '../process-stage-master/configure-bmr',
      icon: 'fa-sitemap',
      category: 'Master Setup',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
      id: 'prepare',
      title: 'Prepare BMR Master',
      route: '../process-stage-master/prepare-bmr-master',
      icon: 'fa-clipboard-check',
      category: 'Master Setup',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
    {
      id: 'process-type',
      title: 'Process Type Master',
      route: '../process-type-master',
      icon: 'fa-project-diagram',
      category: 'Master Setup',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    },
    {
      id: 'review',
      title: 'BMR Review & Approval',
      route: '../bmr-master/bmrdash',
      icon: 'fa-check-double',
      category: 'Workflow',
      gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    },
    {
      id: 'prod-exec',
      title: 'Production Execution',
      route: '/fproduction/ebmr',
      icon: 'fa-play-circle',
      category: 'Execution',
      gradient: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
    },
    {
      id: 'pack-exec',
      title: 'Packing Execution',
      route: '/packing/bmr/new',
      icon: 'fa-truck-loading',
      category: 'Execution',
      gradient: 'linear-gradient(135deg, #f7971e 0%, #ffd200 100%)',
    },
  ];

  constructor(private masterHubReturn: MasterHubReturnService) {}

  ngOnInit(): void {
    this.masterHubReturn.setReturnDepartment(this.deptId);
  }

  registerReturn(): void {
    this.masterHubReturn.setReturnDepartment(this.deptId);
  }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master');
  }
}

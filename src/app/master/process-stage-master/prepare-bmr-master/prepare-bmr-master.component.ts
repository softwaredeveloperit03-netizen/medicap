import { Component, OnInit } from '@angular/core';
import { MasterHubReturnService } from '../../master-hub-return.service';

@Component({
  selector: 'app-prepare-bmr-master',
  templateUrl: './prepare-bmr-master.component.html',
  styleUrls: ['./prepare-bmr-master.component.css'],
})
export class PrepareBmrMasterComponent implements OnInit {
  readonly deptId = 'process-stage';

  constructor(private readonly masterHubReturn: MasterHubReturnService) {}

  ngOnInit(): void {
    this.masterHubReturn.setReturnDepartment(this.deptId);
  }
}

import { Component } from '@angular/core';
import { MasterHubReturnService } from '../../master-hub-return.service';

@Component({
  selector: 'app-process-stage-landing',
  templateUrl: './process-stage-landing.component.html',
  styleUrls: ['./process-stage-landing.component.css'],
})
export class ProcessStageLandingComponent {
  readonly deptId = 'process-stage';

  constructor(private masterHubReturn: MasterHubReturnService) {}

  registerReturn(): void {
    this.masterHubReturn.setReturnDepartment(this.deptId);
  }

  closeFromMasterHub(): void {
    this.masterHubReturn.closeToMasterHubOr('/master');
  }
}
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule } from '@angular/router';
import { CanplanComponent } from './canplan/canplan.component';
import { MrpMaterialAvailabilityComponent } from './mrp-material-availability/mrp-material-availability.component';

@NgModule({
  declarations: [CanplanComponent, MrpMaterialAvailabilityComponent],
  imports: [CommonModule, FormsModule, ClarityModule, RouterModule],
  exports: [CanplanComponent, MrpMaterialAvailabilityComponent],
})
export class PlanningSharedModule {}

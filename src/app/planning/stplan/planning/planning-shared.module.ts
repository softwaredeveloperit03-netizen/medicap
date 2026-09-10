import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule } from '@angular/router';
import { CanplanComponent } from './canplan/canplan.component';

@NgModule({
  declarations: [CanplanComponent],
  imports: [CommonModule, FormsModule, ClarityModule, RouterModule],
  exports: [CanplanComponent],
})
export class PlanningSharedModule {}

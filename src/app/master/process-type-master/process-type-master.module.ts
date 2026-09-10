import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { ProcessTypeMasterFormComponent } from './process-type-master-form.component';
import { ProcessTypeSaveLogComponent } from './process-type-save-log/process-type-save-log.component';

const routes: Routes = [{ path: '', pathMatch: 'full', component: ProcessTypeMasterFormComponent }];

@NgModule({
  declarations: [ProcessTypeMasterFormComponent, ProcessTypeSaveLogComponent],
  imports: [CommonModule, FormsModule, ClarityModule, RouterModule.forChild(routes)],
})
export class ProcessTypeMasterModule {}

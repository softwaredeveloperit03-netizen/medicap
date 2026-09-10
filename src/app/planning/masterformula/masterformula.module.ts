import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { BatchFormulaLogComponent } from './batch-formula-log/batch-formula-log.component';


const routes: Routes = [
  { path: '', component: LogComponent},
  { path: 'batch-formula-log', component: BatchFormulaLogComponent},
];

@NgModule({
  declarations: [LogComponent, BatchFormulaLogComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MasterformulaModule { }

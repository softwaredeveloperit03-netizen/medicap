import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { MultiSelectModule } from 'primeng/multiselect';
import { SharedModule } from 'src/app/shared/shared.module';
import { LogComponent } from './log/log.component';

const routes: Routes = [{ path: '', component: LogComponent }];

@NgModule({
  declarations: [LogComponent],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes),
  ],
})
export class UnitformulaLogModule {}

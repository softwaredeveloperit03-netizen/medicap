import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { BillOfMaterialDashboardComponent } from './dashboard/dashboard.component';

const routes: Routes = [
  { path: '', component: BillOfMaterialDashboardComponent },
  {
    path: 'prepare',
    loadChildren: () =>
      import('../../unitformula/unitformula-log.module').then((m) => m.UnitformulaLogModule),
  },
];

@NgModule({
  declarations: [BillOfMaterialDashboardComponent],
  imports: [CommonModule, SharedModule, RouterModule.forChild(routes)],
})
export class BillOfMaterialModule {}

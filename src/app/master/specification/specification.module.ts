import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { MasterExcelModule } from 'src/app/shared/master-excel/master-excel.module';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ObsoleteSpecificationsComponent } from './obsolete-specifications/obsolete-specifications.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  // Revision tab routes moved to master/specification dashboard context.
  { path: 'specification-revision', redirectTo: '/master/specification/raw/specification-revision', pathMatch: 'full' },
  { path: 'specification-revision/revision-status', redirectTo: '/master/specification/raw/specification-revision/revision-status', pathMatch: 'full' },
  { path: 'specification-revision/revision-history-log', redirectTo: '/master/specification/raw/specification-revision/revision-history-log', pathMatch: 'full' },
  { path: 'specification-revision/periodic-review', redirectTo: '/master/specification/raw/specification-revision/periodic-review', pathMatch: 'full' },
  { path: 'specification-revision/training', redirectTo: '/master/specification/raw/specification-revision/training', pathMatch: 'full' },
  { path: 'specification-revision/implementation', redirectTo: '/master/specification/raw/specification-revision/implementation', pathMatch: 'full' },
  { path: 'specification-revision/view/:id', redirectTo: '/master/specification/raw/specification-revision/view/:id', pathMatch: 'full' },
  { path: 'specification-revision/edit/:id', redirectTo: '/master/specification/raw/specification-revision/edit/:id', pathMatch: 'full' },
  { path: 'obsolete-specifications', component: ObsoleteSpecificationsComponent },
  {
    path: 'raw',
    loadChildren: () =>
      import('../../qc/specifications/raw/raw.module').then((m) => m.RawModule),
    data: { preload: false },
  },
  {
    path: 'water',
    loadChildren: () =>
      import('../../qc/specifications/water/water.module').then(
        (m) => m.WaterModule
      ),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [DashboardComponent, ObsoleteSpecificationsComponent],
  imports: [
    SharedModule,
    MasterExcelModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class SpecificationModule {}

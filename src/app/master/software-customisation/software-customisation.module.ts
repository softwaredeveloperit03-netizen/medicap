import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { SoftwareCustomisationShellComponent } from './software-customisation-shell.component';
import { MoaTypeComponent } from '../moa-type/moa-type.component';

const routes: Routes = [
  {
    path: '',
    component: SoftwareCustomisationShellComponent,
    children: [
      { path: '', redirectTo: 'material-master-form', pathMatch: 'full' },
      {
        path: 'material-master-form',
        loadChildren: () =>
          import('../../qa/soft-restriction/rm-master-customisation/rm-master-customisation.module').then(
            (m) => m.RmMasterCustomisationModule
          ),
        data: { preload: false },
      },
      {
        path: 'specification-form',
        loadChildren: () =>
          import(
            '../../qa/soft-restriction/specification-form-customisation/specification-form-customisation.module'
          ).then((m) => m.SpecificationFormCustomisationModule),
        data: { preload: false },
      },
      {
        path: 'receiving-form',
        loadChildren: () =>
          import(
            '../../qa/soft-restriction/receiving-form-customisation/receiving-form-customisation.module'
          ).then((m) => m.ReceivingFormCustomisationModule),
        data: { preload: false },
      },
      // SoftwareTypeComponent is declared in MasterModule — avoid double-declaration.
      { path: 'software-type', redirectTo: '/master/software-type', pathMatch: 'full' },
      { path: 'moa-type', component: MoaTypeComponent },
    ],
  },
];

@NgModule({
  declarations: [SoftwareCustomisationShellComponent, MoaTypeComponent],
  imports: [CommonModule, FormsModule, ClarityModule, RouterModule.forChild(routes)],
})
export class SoftwareCustomisationModule {}

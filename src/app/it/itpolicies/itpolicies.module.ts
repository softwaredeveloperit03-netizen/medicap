import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { UsrreqformComponent } from './usrreqform/usrreqform.component';
import { VerifConfigComponent } from './verif-config/verif-config.component';

import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { AuthorizedlogComponent } from './authorizedlog/authorizedlog.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'Usrreqform', component: UsrreqformComponent },
  { path: 'verif-config', component: VerifConfigComponent },
   { path: 'authorizedlog', component: AuthorizedlogComponent }
];



@NgModule({
  declarations: [
    UsrreqformComponent,
    VerifConfigComponent,

    DashboardComponent,
     AuthorizedlogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
      FormsModule,
        ClarityModule,
        RouterModule.forChild(routes)
  ]
})
export class ItpoliciesModule { }

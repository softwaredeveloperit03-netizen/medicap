import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AdditionalComponent } from '../employees/additional/additional.component';
import { AccountsComponent } from './accounts/accounts.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'Additional', component: AdditionalComponent},
  { path: 'accounts', component: AccountsComponent},

  { path: 'password', loadChildren: () => import('./password/password.module').then(m=>m.PasswordModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent,AdditionalComponent,AccountsComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class UserModule { }

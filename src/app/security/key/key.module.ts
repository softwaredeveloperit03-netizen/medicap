import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MasterComponent } from './master/master.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RegistorComponent } from './registor/registor.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { NewComponent } from './new/new.component';
import { FormComponent } from './form/form.component';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';



const routes:Routes=[
  {path:'',component: DashboardComponent},
  {path:'master',component: MasterComponent},
  {path:'register',component: RegistorComponent},
  {path:'new',component: NewComponent},
  {path:'form',component: FormComponent},
];
@NgModule({
  declarations: [
    MasterComponent,
    DashboardComponent,
    RegistorComponent,
    NewComponent,
    FormComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes),
    ClarityModule,
    DocsIconsModule
  ]
})
export class KeyModule { }

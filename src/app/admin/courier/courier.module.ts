import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { IncomingComponent } from './incoming/incoming.component';
import { OutgoingComponent } from './outgoing/outgoing.component';
import { NewComponent } from './new/new.component';
import { FormComponent } from './form/form.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'incoming', component: IncomingComponent},
  { path: 'outgoing', component: OutgoingComponent},
  { path: 'new', component: NewComponent},
  { path: 'form', component: FormComponent}
  
];
@NgModule({
  declarations: [
    DashboardComponent,
    IncomingComponent,
    OutgoingComponent,
    NewComponent,
    FormComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class CourierModule { }

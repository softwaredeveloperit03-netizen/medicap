import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ClientManagementComponent } from './client-management/client-management.component';
import { RouterModule, Routes } from '@angular/router';
import { ClientNewComponent } from './client-new/client-new.component';
import { ClarityModule } from '@clr/angular';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';

import { CmdashboardComponent } from './cmdashboard/cmdashboard.component';
import { TechdocdashboardComponent } from './techdocdashboard/techdocdashboard.component';
import { SamplereqdashboardComponent } from './samplereqdashboard/samplereqdashboard.component';
import { TechreqComponent } from './techreq/techreq.component';
import { TechlogComponent } from './techlog/techlog.component';
import { LogComponent } from './log/log.component';
import { NewformComponent } from './newform/newform.component';
import { CheckingComponent } from './checking/checking.component';
import { TranslateModule } from '@ngx-translate/core';


console.warn("inside client master module")

const routes: Routes = [
  { path: '', component: CmdashboardComponent},

  { path: 'clientManagement', component: ClientManagementComponent},
  { path: 'clientNew', component: ClientNewComponent},
  { path: 'techreq', component: TechdocdashboardComponent},
  { path: 'samplereq', component: SamplereqdashboardComponent},
  { path: 'techreqdoc', component: TechreqComponent},
  { path: 'techreqlog', component: TechlogComponent,pathMatch:'full'},
  {path :'sampleLog', component:LogComponent} ,
  {path :'samplenewform', component:NewformComponent} ,
  {path :'checking', component:CheckingComponent} 





  


 
 

];



@NgModule({
  declarations: [
    ClientManagementComponent,
    CmdashboardComponent,
    TechdocdashboardComponent,
    TechreqComponent,
    TechlogComponent,
    LogComponent,
    SamplereqdashboardComponent,
    NewformComponent,
    CheckingComponent,
    ClientNewComponent

   
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    FormsModule,

 


    
    RouterModule.forChild(routes)

  ]
})
export class ClientMasterModule 
{

 }

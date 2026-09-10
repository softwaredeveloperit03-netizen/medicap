import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import {MultiSelectModule} from 'primeng/multiselect';
import { ConfigurationComponent } from './configuration/configuration.component';
import { LogComponent } from './log/log.component';
import { MappedComponent } from './mapped/mapped.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';

 
const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'Configuration', component: ConfigurationComponent},
  { path: 'Log', component: LogComponent},
  { path: 'Map', component: MappedComponent},
  { path: 'New', component: NewComponent},
   
];

@NgModule({
  declarations: [
    DashboardComponent,
    ConfigurationComponent,
    LogComponent,
    MappedComponent,
    NewComponent,
    
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
}) 
export class LinemasterModule { }

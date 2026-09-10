import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboradComponent } from './dashborad/dashborad.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { CleaningComponent } from './cleaning/cleaning.component';
import { LogComponent } from './log/log.component';
import { BodLogComponent } from './bod-log/bod-log.component';
import { LogicalCleaningComponent } from './logical-cleaning/logical-cleaning.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes=[
  {path:'',component:DashboradComponent},
  {path:'cleaning',component:CleaningComponent},
  {path:'log',component:LogComponent},
  {path:'bod-log',component:BodLogComponent},
  {path:'logical-cleaning',component:LogicalCleaningComponent}
];

@NgModule({
  declarations: [
    DashboradComponent,
    CleaningComponent,
    LogComponent,
    BodLogComponent,
    LogicalCleaningComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
    
  ]
})
export class IncubatorModule { }

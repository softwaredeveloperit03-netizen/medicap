import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LogComponent } from './log/log.component';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ApprovalComponent } from './approval/approval.component';
import { CoffComponent } from './coff/coff.component';
import { CoffappComponent } from './coffapp/coffapp.component';
import { TranslateModule } from '@ngx-translate/core';





const routes: Routes = [
  { path: '', component: LogComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'coff', component: CoffComponent},
  { path: 'coffapp', component: CoffappComponent},
]

@NgModule({
  declarations: [LogComponent,NewComponent,ApprovalComponent, CoffComponent, CoffappComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})


export class LeaveModule { }

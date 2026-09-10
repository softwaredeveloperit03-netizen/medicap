import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ApproveComponent } from './approve/approve.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'new', component: NewComponent},
  { path: 'approve', component: ApproveComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  declarations: [ NewComponent, ApproveComponent, LogComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AgentModule { }

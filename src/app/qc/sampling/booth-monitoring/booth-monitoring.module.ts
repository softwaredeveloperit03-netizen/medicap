import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DocsIconsModule } from '../../../floating-docs-popup/docs-icons.module';

import { DashboardComponent } from './dashboard/dashboard.component';
import { NewLogComponent } from './new-log/new-log.component';
import { LogListComponent } from './log-list/log-list.component';
import { LogViewComponent } from './log-view/log-view.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewLogComponent },
  { path: 'log', component: LogListComponent },
  { path: 'view/:id', component: LogViewComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewLogComponent,
    LogListComponent,
    LogViewComponent,
  ],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    SharedModule,
    TranslateModule,
    DocsIconsModule,
    RouterModule.forChild(routes),
  ],
})
export class BoothMonitoringModule {}

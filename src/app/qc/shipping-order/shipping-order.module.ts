import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';

import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { ViewComponent } from './view/view.component';
import { CheckedByPanelComponent } from './checked-by-panel/checked-by-panel.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'log', component: LogComponent },
  { path: 'view/:id', component: ViewComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    LogComponent,
    ViewComponent,
    CheckedByPanelComponent,
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
export class ShippingOrderModule {}

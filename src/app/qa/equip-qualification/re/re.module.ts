import { NgModule} from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { OperationalQComponent } from './operational-q/operational-q.component';
import { InstallationQComponent } from './installation-q/installation-q.component';
import { PerformanceQComponent } from './performance-q/performance-q.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'installation', component: InstallationQComponent },
  { path: 'operation', component: OperationalQComponent },
  { path: 'permormance', component: PerformanceQComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    InstallationQComponent,
    OperationalQComponent,
    PerformanceQComponent,
  ],
  imports: [
    SharedModule, TranslateModule,CommonModule, RouterModule.forChild(routes)],
})
export class ReModule {}

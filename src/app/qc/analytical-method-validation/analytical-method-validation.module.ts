import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { AmvDashboardComponent } from './amv-dashboard.component';

const routes: Routes = [{ path: '', component: AmvDashboardComponent }];

@NgModule({
  declarations: [AmvDashboardComponent],
  imports: [SharedModule, TranslateModule, CommonModule, RouterModule.forChild(routes)],
})
export class AnalyticalMethodValidationModule {}

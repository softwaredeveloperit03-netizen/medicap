import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { ProcessStageLandingComponent } from './process-stage-landing/process-stage-landing.component';
import { ConfigureBmrComponent } from './configure-bmr/configure-bmr.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', component: ProcessStageLandingComponent },
  { path: 'configure-bmr', component: ConfigureBmrComponent },
  // Prepare BMR Master uses the real seeded BMR/PI Master list (not the old placeholder page).
  { path: 'prepare-bmr-master', redirectTo: '/master/bmr-master/bmrdash', pathMatch: 'full' },
];

@NgModule({
  declarations: [ProcessStageLandingComponent, ConfigureBmrComponent],
  imports: [CommonModule, FormsModule, ClarityModule, TranslateModule, RouterModule.forChild(routes)],
})
export class ProcessStageMasterModule {}

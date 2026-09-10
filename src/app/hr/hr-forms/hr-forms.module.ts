import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { HttpClientModule } from '@angular/common/http';

import { HrFormsRoutingModule } from './hr-forms-routing.module';
import { RecruitmentComponent } from './recruitment/recruitment.component';
import { StatutoriesComponent } from './statutories/statutories.component';
import { EngagementComponent } from './engagement/engagement.component';
import { RAndRComponent } from './r-and-r/r-and-r.component';
import { PerformanceComponent } from './performance/performance.component';
import { RecordsFormatsComponent } from './records-formats/records-formats.component';
import { TrainingComponent } from './training/training.component';
import { HiringComponent } from './hiring/hiring.component';
import { HrLettersComponent } from './hr-letters/hr-letters.component';
import { CompensationComponent } from './compensation/compensation.component';
import { PolicyComponent } from './policy/policy.component';
import { ExitComponent } from './exit/exit.component';
import { OnboardingComponent } from './onboarding/onboarding.component';
import { CompanyFormsComponent } from './company-forms/company-forms.component';
import { HrFormsComponent } from './hr-forms/hr-forms.component';
import { JdComponent } from './jd/jd.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [RecruitmentComponent, StatutoriesComponent, EngagementComponent, RAndRComponent, PerformanceComponent, RecordsFormatsComponent, TrainingComponent, HiringComponent, HrLettersComponent, CompensationComponent, PolicyComponent, ExitComponent, OnboardingComponent, CompanyFormsComponent, HrFormsComponent, JdComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
    HrFormsRoutingModule
  ]
})
export class HrFormsModule { }

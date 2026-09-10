import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { CompanyFormsComponent } from './company-forms/company-forms.component';
import { CompensationComponent } from './compensation/compensation.component';
import { EngagementComponent } from './engagement/engagement.component';
import { ExitComponent } from './exit/exit.component';
import { HiringComponent } from './hiring/hiring.component';
import { HrFormsComponent } from './hr-forms/hr-forms.component';
import { HrLettersComponent } from './hr-letters/hr-letters.component';
import { JdComponent } from './jd/jd.component';
import { OnboardingComponent } from './onboarding/onboarding.component';
import { PerformanceComponent } from './performance/performance.component';
import { PolicyComponent } from './policy/policy.component';
import { RAndRComponent } from './r-and-r/r-and-r.component';
import { RecordsFormatsComponent } from './records-formats/records-formats.component';
import { RecruitmentComponent } from './recruitment/recruitment.component';
import { StatutoriesComponent } from './statutories/statutories.component';
import { TrainingComponent } from './training/training.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path:'company-forms', component: CompanyFormsComponent },
  { path:'compensation', component: CompensationComponent },
  { path:'engagement', component: EngagementComponent },
  { path:'exit', component: ExitComponent },
  { path:'hiring', component: HiringComponent },
  { path:'hr-forms', component: HrFormsComponent },
  { path:'hr-letters', component: HrLettersComponent },
  { path:'onboarding', component: OnboardingComponent },
  { path:'performance', component: PerformanceComponent },
  { path:'policy', component: PolicyComponent },
  { path:'r-and-r', component: RAndRComponent },
  { path:'records-formats', component: RecordsFormatsComponent },
  { path:'recruitment', component: RecruitmentComponent },
  { path:'statutories', component: StatutoriesComponent },
  { path:'training', component: TrainingComponent },
  { path:'jd', component: JdComponent },

];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class HrFormsRoutingModule { }

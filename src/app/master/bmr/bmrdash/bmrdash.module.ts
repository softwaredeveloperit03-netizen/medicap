import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { EditorModule } from '@tinymce/tinymce-angular';
import { RouterModule, Routes } from '@angular/router';
import { MultiSelectModule } from 'primeng/multiselect';
import { StandardComponent } from './standard/standard.component';
import { BmrComponent } from './bmr/bmr.component';
import { QuillModule } from 'ngx-quill';
import { BmrReviewComponent } from './bmr-review/bmr-review.component';
import { BmrApprvalComponent } from './bmr-apprval/bmr-apprval.component';
import { BmrViewComponent } from './bmr-view/bmr-view.component';
import { TranslateModule } from '@ngx-translate/core';





const routes: Routes = [
  { path: '', component: BmrComponent},
  { path: 'standard', component: StandardComponent},
 
  { path: 'BmrReview/:id', component: BmrReviewComponent},
  { path: 'Bmrapproval/:id', component: BmrApprvalComponent},
  { path: 'BmrView/:id', component: BmrViewComponent},

  
];
@NgModule({
  declarations: [
    DashboardComponent,StandardComponent,BmrComponent,BmrReviewComponent,BmrApprvalComponent,BmrViewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    EditorModule,
    FormsModule,
    QuillModule.forRoot(),
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class BmrdashModule { }

import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { EditorModule } from '@tinymce/tinymce-angular';
import { PdfViewerModule } from 'ng2-pdf-viewer';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent }
]
@NgModule({
  declarations: [DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    FormsModule,
    CommonModule,
    ReactiveFormsModule,
    ClarityModule,
    EditorModule,
    PdfViewerModule,
    RouterModule.forChild(routes)
  ]
})
export class LogbookModule { }

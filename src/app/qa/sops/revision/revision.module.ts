import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RevisionRoutingModule } from './revision-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RequestComponent } from './request/request.component';
import { EditComponent } from './edit/edit.component';
import { HistoryComponent } from './history/history.component';
import { LogComponent } from './log/log.component';
import { RequestCheckingComponent } from './request-checking/request-checking.component';
import { PdfViewerModule } from 'ng2-pdf-viewer';
import { ChangecontrolComponent } from './changecontrol/changecontrol.component';
import { EditorModule } from '@tinymce/tinymce-angular';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [DashboardComponent, RequestComponent, EditComponent, HistoryComponent, LogComponent, RequestCheckingComponent, ChangecontrolComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    RevisionRoutingModule,
    FormsModule,
    ClarityModule,
    PdfViewerModule,
    EditorModule
  ]
})
export class RevisionModule { }

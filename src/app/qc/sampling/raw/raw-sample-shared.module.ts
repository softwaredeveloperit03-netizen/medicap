import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { SampleComponent } from './sample/sample.component';
import { CheckingComponent } from './checking/checking.component';
import { ApproveComponent } from './approve/approve.component';
import { LogComponent } from './log/log.component';

@NgModule({
  declarations: [SampleComponent, CheckingComponent, ApproveComponent, LogComponent],
  imports: [CommonModule, FormsModule, SharedModule, TranslateModule, RouterModule, ClarityModule],
  exports: [SampleComponent, CheckingComponent, ApproveComponent, LogComponent],
})
export class RawSampleSharedModule {}

import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { DocsIconsModule } from '../../floating-docs-popup/docs-icons.module';
import { QcModuleDashboardShellComponent } from './qc-module-dashboard-shell.component';

@NgModule({
  declarations: [QcModuleDashboardShellComponent],
  imports: [CommonModule, FormsModule, RouterModule, TranslateModule, DocsIconsModule],
  exports: [QcModuleDashboardShellComponent, DocsIconsModule],
})
export class QcModuleDashboardModule {}

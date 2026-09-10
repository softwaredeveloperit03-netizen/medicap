import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReqAnalysisComponent } from './req-analysis.component';

describe('ReqAnalysisComponent', () => {
  let component: ReqAnalysisComponent;
  let fixture: ComponentFixture<ReqAnalysisComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReqAnalysisComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReqAnalysisComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

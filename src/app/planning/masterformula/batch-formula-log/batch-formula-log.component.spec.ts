import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BatchFormulaLogComponent } from './batch-formula-log.component';

describe('BatchFormulaLogComponent', () => {
  let component: BatchFormulaLogComponent;
  let fixture: ComponentFixture<BatchFormulaLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BatchFormulaLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BatchFormulaLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

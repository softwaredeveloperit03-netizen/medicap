import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QaheadreviewComponent } from './qaheadreview.component';

describe('QaheadreviewComponent', () => {
  let component: QaheadreviewComponent;
  let fixture: ComponentFixture<QaheadreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QaheadreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QaheadreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

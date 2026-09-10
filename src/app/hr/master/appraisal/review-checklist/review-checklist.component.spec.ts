import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReviewChecklistComponent } from './review-checklist.component';

describe('ReviewChecklistComponent', () => {
  let component: ReviewChecklistComponent;
  let fixture: ComponentFixture<ReviewChecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReviewChecklistComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(ReviewChecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CrossChecklistComponent } from './cross-checklist.component';

describe('CrossChecklistComponent', () => {
  let component: CrossChecklistComponent;
  let fixture: ComponentFixture<CrossChecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CrossChecklistComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CrossChecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

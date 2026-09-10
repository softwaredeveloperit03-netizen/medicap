import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PreliminaryComponent } from './preliminary.component';

describe('PreliminaryComponent', () => {
  let component: PreliminaryComponent;
  let fixture: ComponentFixture<PreliminaryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PreliminaryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PreliminaryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

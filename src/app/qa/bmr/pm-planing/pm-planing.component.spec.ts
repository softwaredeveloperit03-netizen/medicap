import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PmPlaningComponent } from './pm-planing.component';

describe('PmPlaningComponent', () => {
  let component: PmPlaningComponent;
  let fixture: ComponentFixture<PmPlaningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PmPlaningComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PmPlaningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

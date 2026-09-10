import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HygineComponent } from './hygine.component';

describe('HygineComponent', () => {
  let component: HygineComponent;
  let fixture: ComponentFixture<HygineComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ HygineComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(HygineComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

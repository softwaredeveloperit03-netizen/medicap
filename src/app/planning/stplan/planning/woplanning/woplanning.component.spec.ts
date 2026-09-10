import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WoplanningComponent } from './woplanning.component';

describe('WoplanningComponent', () => {
  let component: WoplanningComponent;
  let fixture: ComponentFixture<WoplanningComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WoplanningComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WoplanningComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

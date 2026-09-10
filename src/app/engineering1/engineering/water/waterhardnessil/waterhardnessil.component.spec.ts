import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WaterhardnessilComponent } from './waterhardnessil.component';

describe('WaterhardnessilComponent', () => {
  let component: WaterhardnessilComponent;
  let fixture: ComponentFixture<WaterhardnessilComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WaterhardnessilComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WaterhardnessilComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

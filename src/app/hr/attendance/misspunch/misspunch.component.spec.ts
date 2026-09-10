import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MisspunchComponent } from './misspunch.component';

describe('MisspunchComponent', () => {
  let component: MisspunchComponent;
  let fixture: ComponentFixture<MisspunchComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MisspunchComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MisspunchComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

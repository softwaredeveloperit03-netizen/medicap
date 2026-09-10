import { ComponentFixture, TestBed } from '@angular/core/testing';

import { KirloskerComponent } from './kirlosker.component';

describe('KirloskerComponent', () => {
  let component: KirloskerComponent;
  let fixture: ComponentFixture<KirloskerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ KirloskerComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(KirloskerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

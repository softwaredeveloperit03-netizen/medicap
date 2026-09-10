import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FolloeupComponent } from './folloeup.component';

describe('FolloeupComponent', () => {
  let component: FolloeupComponent;
  let fixture: ComponentFixture<FolloeupComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FolloeupComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FolloeupComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

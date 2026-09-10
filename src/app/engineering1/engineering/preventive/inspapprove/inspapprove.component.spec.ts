import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InspapproveComponent } from './inspapprove.component';

describe('InspapproveComponent', () => {
  let component: InspapproveComponent;
  let fixture: ComponentFixture<InspapproveComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InspapproveComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InspapproveComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

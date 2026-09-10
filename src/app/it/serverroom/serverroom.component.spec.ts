import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ServerroomComponent } from './serverroom.component';

describe('ServerroomComponent', () => {
  let component: ServerroomComponent;
  let fixture: ComponentFixture<ServerroomComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ServerroomComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ServerroomComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
